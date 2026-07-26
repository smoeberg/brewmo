<?php
// Disable Brewmo module by force (entity=1 by default). Requires admin rights.
$res=0;
$paths=array(__DIR__.'/../../main.inc.php',__DIR__.'/../../../main.inc.php',__DIR__.'/../../../../main.inc.php');
foreach($paths as $p){ if(!$res && file_exists($p)) $res=@include $p; }
if(!$res){ die('Include of main.inc.php failed'); }
if (empty($user->admin)) accessforbidden();

$e = (int) GETPOST('entity','int'); if ($e<=0) $e = (int) $conf->entity;

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dolibarr_set_const($db, 'MAIN_MODULE_BREWMO', '0', 'chaine', 0, '', $e);
header('Content-Type: text/plain; charset=UTF-8');
echo "MAIN_MODULE_BREWMO=0 for entity=".$e."\nDone. Go to Setup -> Modules and refresh.";
