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
header('Content-Type: application/json'); echo json_encode(array('ok'=>true));