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
$_GET['leftmenu']  = 'brewmo_batches';

dol_include_once('/brewmo/class/brewsession.class.php');
dol_include_once('/brewmo/class/recipe.class.php');
dol_include_once('/brewmo/class/brewtank.class.php');

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();

llxHeader('', $langs->trans("BrewSessions"));

print load_fiche_titre($langs->trans("BrewSessions"));

print '<div class="tabsAction">';
if ($user->rights->brewmo->write) {
    print '<a class="butAction" href="'.dol_buildpath('/brewmo/www/brewsession_card.php', 1).'">'.$langs->trans("NewBrewSession").'</a>';
}
print '</div>';

$sql = "SELECT s.rowid, s.ref, s.status, s.volume_l, r.ref as reciperef, t.ref as tankref";
$sql .= " FROM ".$db->prefix()."brew_brewsession as s";
$sql .= " LEFT JOIN ".$db->prefix()."brew_recipes as r ON r.rowid = s.fk_recipe";
$sql .= " LEFT JOIN ".$db->prefix()."brew_tank as t ON t.rowid = s.fk_tank";
$sql .= " WHERE s.entity = ".((int) $conf->entity);
$sql .= " ORDER BY s.rowid DESC";

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);

print '<div class="div-table-responsive">';
print '<table class="liste">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Ref").'</th>';
print '<th>'.$langs->trans("Recipe").'</th>';
print '<th>'.$langs->trans("Tank").'</th>';
print '<th>'.$langs->trans("Status").'</th>';
print '<th>'.$langs->trans("Volume").'</th>';
print '</tr>';

while ($obj = $db->fetch_object($resql)) {
    $url = dol_buildpath('/brewmo/www/brewsession_card.php', 1).'?id='.$obj->rowid;
    print '<tr>';
    print '<td><a href="'.$url.'">'.dol_escape_htmltag($obj->ref).'</a></td>';
    print '<td>'.dol_escape_htmltag($obj->reciperef).'</td>';
    print '<td>'.dol_escape_htmltag($obj->tankref).'</td>';
    print '<td>'.dol_escape_htmltag($obj->status).'</td>';
    print '<td>'.($obj->volume_l !== null ? price($obj->volume_l, 0).' L' : '').'</td>';
    print '</tr>';
}
print '</table>';
print '</div>';

llxFooter();
