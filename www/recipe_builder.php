<?php
$res = 0;
$paths = array(
    __DIR__ . '/../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php',
    __DIR__ . '/../main.inc.php',
    __DIR__ . '/main.inc.php',
    '../../main.inc.php',
    '../../../main.inc.php',
    '../../../../main.inc.php',
    '../main.inc.php',
    'main.inc.php'
);
foreach ($paths as $p) { if (!$res && file_exists($p)) { $res = @include $p; } }
if (!$res) { die('Include of main.inc.php failed'); }
$_GET['mainmenu']='brewmo'; $_GET['leftmenu']='brewmo_recipes';
$langs->load('brewmo@brewmo'); if (empty($user->rights->brewmo->read)) accessforbidden(); llxHeader('', $langs->trans('RecipeBuilder')); print load_fiche_titre($langs->trans('RecipeBuilder')); print '<p>'.$langs->trans('ComingSoon').'</p>'; llxFooter();