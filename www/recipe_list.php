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
$_GET['leftmenu']  = 'brewmo_recipes';

dol_include_once('/brewmo/class/recipe.class.php');

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read)) accessforbidden();

llxHeader('', $langs->trans("Recipes"));

print load_fiche_titre($langs->trans("Recipes"));

print '<div class="tabsAction">';
if ($user->rights->brewmo->write) {
    print '<a class="butAction" href="'.dol_buildpath('/brewmo/www/recipe_edit.php', 1).'">'.$langs->trans("NewRecipe").'</a>';
}
print '</div>';

$sql = "SELECT rowid, ref, label, abv, ibu, color_ebc, batch_volume_l";
$sql .= " FROM ".$db->prefix()."brew_recipes";
$sql .= " WHERE entity = ".((int) $conf->entity);
$sql .= " ORDER BY ref ASC";

$resql = $db->query($sql);
if (!$resql) dol_print_error($db);

print '<div class="div-table-responsive">';
print '<table class="liste">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("Ref").'</th>';
print '<th>'.$langs->trans("Label").'</th>';
print '<th>ABV%</th><th>IBU</th><th>EBC</th><th>'.$langs->trans("Volume").'</th>';
print '</tr>';

while ($obj = $db->fetch_object($resql)) {
    $url = dol_buildpath('/brewmo/www/recipe_edit.php', 1).'?id='.$obj->rowid;
    print '<tr>';
    print '<td><a href="'.$url.'">'.dol_escape_htmltag($obj->ref).'</a></td>';
    print '<td>'.dol_escape_htmltag($obj->label).'</td>';
    print '<td>'.($obj->abv !== null ? price($obj->abv, 2) : '').'</td>';
    print '<td>'.($obj->ibu !== null ? price($obj->ibu, 0) : '').'</td>';
    print '<td>'.($obj->color_ebc !== null ? price($obj->color_ebc, 0) : '').'</td>';
    print '<td>'.($obj->batch_volume_l !== null ? price($obj->batch_volume_l, 0).' L' : '').'</td>';
    print '</tr>';
}
print '</table>';
print '</div>';

llxFooter();
