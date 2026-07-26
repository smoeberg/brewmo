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

if (file_exists(DOL_DOCUMENT_ROOT . '/custom/brewmo/class/recipe.class.php')) {
    dol_include_once('/custom/brewmo/class/recipe.class.php');
} else {
    dol_include_once('/brewmo/class/recipe.class.php');
}

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read) && empty($user->admin)) accessforbidden();

llxHeader('', $langs->trans("Recipes"));

print load_fiche_titre($langs->trans("Recipes"));

print '<div class="tabsAction">';
if (!empty($user->rights->brewmo->write) || !empty($user->admin)) {
    print '<a class="butAction" href="recipe_edit.php">' . $langs->trans("NewRecipe") . '</a>';
}
print '</div>';

$table_v2 = MAIN_DB_PREFIX . "brew_recipe";
$table_v1 = MAIN_DB_PREFIX . "brew_recipes";

$table = $table_v2;
$check_v2 = $db->query("SHOW TABLES LIKE '" . $table_v2 . "'");
if (!$check_v2 || $db->num_rows($check_v2) === 0) {
    $check_v1 = $db->query("SHOW TABLES LIKE '" . $table_v1 . "'");
    if ($check_v1 && $db->num_rows($check_v1) > 0) {
        $table = $table_v1;
    } else {
        $table = null;
    }
}

if ($table === null) {
    print '<div class="warning"><strong>BrewMo opskriftstabeller mangler i databasen:</strong><br>';
    print 'Gå venligst til <strong>Opsætning → Moduler</strong> i Dolibarr, slå <strong>BrewMo</strong> fra og aktiver det igen for at oprette tabellerne.</div>';
    llxFooter();
    exit;
}

if ($table === $table_v2) {
    $sql = "SELECT rowid, ref, title as label, og, fg, ibu, ebc as color_ebc, target_batch_size as batch_volume_l ";
    $sql .= "FROM " . $table . " ";
    $sql .= "ORDER BY rowid DESC";
} else {
    $sql = "SELECT rowid, ref, label, abv, ibu, color_ebc, batch_volume_l ";
    $sql .= "FROM " . $table . " ";
    $sql .= "ORDER BY rowid DESC";
}

$resql = $db->query($sql);
if ($resql) {
    $num = $db->num_rows($resql);
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>Ref</td>';
    print '<td>Titel / Navn</td>';
    print '<td>Målstørrelse (L)</td>';
    print '<td>IBU</td>';
    print '<td>Farve (EBC)</td>';
    print '</tr>';

    if ($num > 0) {
        while ($obj = $db->fetch_object($resql)) {
            print '<tr class="oddeven">';
            print '<td><a href="recipe_edit.php?id=' . $obj->rowid . '">' . htmlspecialchars($obj->ref) . '</a></td>';
            print '<td>' . htmlspecialchars($obj->label) . '</td>';
            print '<td>' . htmlspecialchars($obj->batch_volume_l ?? '0') . '</td>';
            print '<td>' . htmlspecialchars($obj->ibu ?? '0') . '</td>';
            print '<td>' . htmlspecialchars($obj->color_ebc ?? '0') . '</td>';
            print '</tr>';
        }
    } else {
        print '<tr><td colspan="5" class="opacitymedium">Ingen opskrifter fundet. Klik på "Ny opskrift" for at oprette en.</td></tr>';
    }
    print '</table>';
} else {
    print '<div class="error">Database Query Fejl: ' . htmlspecialchars($db->lasterror()) . '</div>';
}

llxFooter();
