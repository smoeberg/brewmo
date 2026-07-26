<?php
$res = 0;
$paths = array(
    __DIR__ . '/../../main.inc.php',
    __DIR__ . '/../../../main.inc.php',
    __DIR__ . '/../../../../main.inc.php'
);
foreach ($paths as $p) {
    if (!$res && file_exists($p)) { $res = @include $p; }
}
if (!$res) { die('Include of main.inc.php failed'); }

dol_include_once('/brewmo/class/brewcalc.class.php');

header('Content-Type: application/json');

$og = (float) GETPOST('og_sg', 'alpha');
$fg = (float) GETPOST('fg_sg', 'alpha');

$abv = BrewmoCalc::abv($og, $fg);

echo json_encode(array(
    'og_sg' => $og,
    'fg_sg' => $fg,
    'abv'   => $abv
));
