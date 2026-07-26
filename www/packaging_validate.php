<?php
// Robust Dolibarr loader (works from custom modules regardless of nesting) - no closing PHP tag
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
$_GET['mainmenu']='brewmo'; $_GET['leftmenu']='brewmo_packaging';
$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->write)) accessforbidden();
$action = 'BREW_PACKAGING_VALIDATE';
setEventMessages($langs->trans("Validated"), null, 'mesgs');
header('Location: '.dol_buildpath('/brewmo/www/packaging_list.php',1));
exit;
