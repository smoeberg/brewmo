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
$_GET['mainmenu']='brewmo'; $_GET['leftmenu']='brewmo_definitions';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
dol_include_once('/core/class/html.form.class.php');
$langs->load('brewmo@brewmo'); $langs->load('admin');
if (empty($user->rights->brewmo->admin)) accessforbidden();
$action = GETPOST('action','alpha'); $token=newToken();

if ($action=='add_packaging_type' && GETPOST('token','alpha')==$_SESSION['newtoken']) {
    $label = trim(GETPOST('label','alpha'));
    if ($label!=='') {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."brew_packaging_type(label, active, entity) VALUES('".$db->escape($label)."',1,".(int)$conf->entity.")";
        $db->query($sql);
        setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentities("Label")), null, 'errors');
    }
}
if ($action=='toggle_packaging_type' && GETPOST('token','alpha')==$_SESSION['newtoken']) {
    $id = (int) GETPOST('id','int');
    $db->query("UPDATE ".MAIN_DB_PREFIX."brew_packaging_type SET active = 1 - active WHERE rowid=".$id." AND entity=".(int)$conf->entity);
    setEventMessages($langs->trans("StatusModified"), null, 'mesgs');
}
if ($action=='del_packaging_type' && GETPOST('token','alpha')==$_SESSION['newtoken']) {
    $id = (int) GETPOST('id','int');
    $db->query("DELETE FROM ".MAIN_DB_PREFIX."brew_packaging_type WHERE rowid=".$id." AND entity=".(int)$conf->entity);
    setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
}

llxHeader('', $langs->trans("Definitions"));
print load_fiche_titre($langs->trans("Definitions"));

print '<div class="tabs">';
print '<a class="tab" href="'.dol_buildpath('/brewmo/admin/setup.php',1).'">'.$langs->trans("BrewmoSetup").'</a>';
print '</div>';

print '<div class="fichecenter">';
print '<h3>'.$langs->trans("PackagingTypes").'</h3>';
print '<form method="post">';
print '<input type="hidden" name="token" value="'.$token.'">';
print '<input type="hidden" name="action" value="add_packaging_type">';
print '<input type="text" name="label" value="" placeholder="'.$langs->trans("Label").'">';
print ' <input class="button" type="submit" value="'.$langs->trans("Add").'">';
print '</form>';

$sql = "SELECT rowid, label, active FROM ".MAIN_DB_PREFIX."brew_packaging_type WHERE entity=".(int)$conf->entity." ORDER BY label ASC";
$resql = $db->query($sql);
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Label").'</td><td class="center">'.$langs->trans("Status").'</td><td class="right">'.$langs->trans("Action").'</td></tr>';
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        print '<tr><td>'.dol_escape_htmltag($obj->label).'</td>';
        print '<td class="center">'.($obj->active? $langs->trans("Enabled") : $langs->trans("Disabled")).'</td>';
        print '<td class="right">';
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=toggle_packaging_type&id='.$obj->rowid.'&token='.$token.'">'.$langs->trans("EnableDisable").'</a> ';
        print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=del_packaging_type&id='.$obj->rowid.'&token='.$token.'" onclick="return confirm(\''.$langs->trans("ConfirmDelete").'\')">'.$langs->trans("Delete").'</a>';
        print '</td></tr>';
    }
}
print '</table>';
print '</div>';

llxFooter();
