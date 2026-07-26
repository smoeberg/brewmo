<?php
$res = 0;
$paths = array(
    __DIR__ . '/../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php'
);
foreach ($paths as $p) { if (!$res && file_exists($p)) { $res = @include $p; } }
if (!$res) { die('Include of main.inc.php failed'); }

$_GET['mainmenu']  = 'brewmo';
$_GET['leftmenu']  = '';

$langs->load('brewmo@brewmo');

llxHeader('', 'Brewmo', '');

print load_fiche_titre('Brewmo');
print '<p>Brewery module landing page.</p>';

llxFooter();
