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
        $this->name_alias = 'BrewMo 2.0';
        $this->description = 'BrewMo - Brewery Management & Manufacturing Execution System for Dolibarr';
        $this->version = '2.0.0';
        $this->family = 'other';
        $this->editor_name = 'Rool';
        $this->editor_url = 'https://rool.app';
        $this->special = 0;
        $this->picto = 'object_generic';

        // Directory name & Module parts
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

        // Menus definition
        $this->menu = array();
        $m = 0;

        // Top Menu Entry
        $this->menu[$m] = array(
            'fk_menu' => 'fk_mainmenu=home',
            'type' => 'top',
            'titre' => 'BrewMo',
            'mainmenu' => 'brewmo',
            'leftmenu' => '',
            'url' => '/custom/brewmo/www/brewsession_list.php',
            'langs' => 'brewmo@brewmo',
            'position' => 1000,
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
        $this->_init(array(), $options);

        // Populate Dolibarr Dictionary "Type of resources" (llx_c_type_resource)
        $resourceTypes = array(
            array('code' => 'RES_BREW_MASHTUN',         'label' => 'Mæskekar (Mash Tun)'),
            array('code' => 'RES_BREW_LAUTER',          'label' => 'Si-kar (Lauter Tun)'),
            array('code' => 'RES_BREW_KETTLE',          'label' => 'Brygkedel (Brew Kettle)'),
            array('code' => 'RES_BREW_WHIRLPOOL',       'label' => 'Whirlpool'),
            array('code' => 'RES_BREW_COMBIBREWHOUSE',  'label' => 'Kombi-bryghus (Combi Brewhouse)'),
            array('code' => 'RES_BREW_FERMENTER',       'label' => 'Gæringstank / CCT (Fermenter)'),
            array('code' => 'RES_BREW_BRITETANK',       'label' => 'Lagertank / BBT (Brite Beer Tank)'),
            array('code' => 'RES_BREW_SERVINGTANK',     'label' => 'Udskænkningstank (Serving Tank)'),
            array('code' => 'RES_BREW_OPENFERMENTER',   'label' => 'Åbent gæringskar (Open Fermenter)'),
            array('code' => 'RES_BREW_HORIZONTALTANK',  'label' => 'Horisontal lagertank (Lager Tank)'),
            array('code' => 'RES_BREW_BOTTLINGLINE',    'label' => 'Flaskefyldelinje (Bottling Line)'),
            array('code' => 'RES_BREW_CANNINGLINE',     'label' => 'Dåsefyldelinje (Canning Line)'),
            array('code' => 'RES_BREW_KEGFILLER',       'label' => 'Fustagefylder (Keg Washer & Filler)'),
            array('code' => 'RES_BREW_LABELER',         'label' => 'Etiketteringsmaskine (Labeler)'),
            array('code' => 'RES_BREW_PACKAGER',        'label' => 'Kartonpakker / Pakkelinje (Case Packer)'),
            array('code' => 'RES_BREW_CIPSTATION',      'label' => 'CIP-anlæg / Rengøringsstation (CIP System)'),
            array('code' => 'RES_BREW_GRAINMILL',       'label' => 'Valsestol / Maltkværn (Grain Mill)'),
            array('code' => 'RES_BREW_PASTEURIZER',     'label' => 'Pasteuriseringsanlæg (Pasteurizer)'),
            array('code' => 'RES_BREW_FILTER',          'label' => 'Filtreringsanlæg (Beer Filter)'),
            array('code' => 'RES_BREW_IOTSENSOR',       'label' => 'IoT / Densitet- & Temperatursensor')
        );

        foreach ($resourceTypes as $rt) {
            $checkSql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "c_type_resource WHERE code = '" . $this->db->escape($rt['code']) . "'";
            $resql = $this->db->query($checkSql);
            if ($resql && $this->db->num_rows($resql) == 0) {
                $insertSql = "INSERT INTO " . MAIN_DB_PREFIX . "c_type_resource (code, label, active) VALUES ('" . $this->db->escape($rt['code']) . "', '" . $this->db->escape($rt['label']) . "', 1)";
                $this->db->query($insertSql);
            }
        }

        // Explicitly enable module constants
        dolibarr_set_const($this->db, "MAIN_MODULE_BREWMO", "1", 'chaine', 0, '', 0);
        dolibarr_set_const($this->db, "MAIN_MODULE_BREWMO_VERSION", $this->version, 'chaine', 0, '', 0);

        return 1;
    }

    public function remove($options = '')
    {
        $this->_remove(array(), $options);

        dolibarr_del_const($this->db, "MAIN_MODULE_BREWMO", 0);
        dolibarr_del_const($this->db, "MAIN_MODULE_BREWMO_VERSION", 0);

        return 1;
    }
}
