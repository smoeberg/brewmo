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
$_GET['leftmenu']  = 'brewmo_tanks';

dol_include_once('/brewmo/class/brewtank.class.php');

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();

llxHeader('', $langs->trans("BrewmoTanks"));

print load_fiche_titre($langs->trans("BrewmoTanks"));

print '<div class="tabsAction">';
if ($user->rights->brewmo->write) {
    print '<a class="butAction" href="'.dol_buildpath('/brewmo/www/tank_card.php', 1).'">'.$langs->trans("NewTank").'</a>';
}
print '</div>';

$sql = "SELECT rowid, ref, label, capacity_l, tank_type, location, is_active";
$sql .= " FROM ".$db->prefix()."brew_tank";
$sql .= " WHERE entity = ".((int) $conf->entity);
$sql .= " ORDER BY ref";

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);

print '<div class="div-table-responsive">';
print '<table class="liste">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Ref").'</th>';
print '<th>'.$langs->trans("Label").'</th>';
print '<th>'.$langs->trans("Capacity").'</th>';
print '<th>'.$langs->trans("Type").'</th>';
print '<th>'.$langs->trans("Location").'</th>';
print '<th>'.$langs->trans("Status").'</th>';
print '</tr>';

while ($obj = $db->fetch_object($resql)) {
    $url = dol_buildpath('/brewmo/www/tank_card.php', 1).'?id='.$obj->rowid;
    print '<tr>';
    print '<td><a href="'.$url.'">'.dol_escape_htmltag($obj->ref).'</a></td>';
    print '<td>'.dol_escape_htmltag($obj->label).'</td>';
    print '<td>'.price($obj->capacity_l, 0).' L</td>';
    print '<td>'.dol_escape_htmltag($obj->tank_type).'</td>';
    print '<td>'.dol_escape_htmltag($obj->location).'</td>';
    print '<td>'.($obj->is_active ? $langs->trans("Active") : $langs->trans("Disabled")).'</td>';
    print '</tr>';
}
print '</table>';
print '</div>';

llxFooter();
