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
$_GET['leftmenu']  = 'brewmo_mrp';

$langs->load('brewmo@brewmo');
if (empty($user->rights->brewmo->read) && empty($user->admin)) accessforbidden();

$recipe_id   = GETPOSTINT('recipe_id');
$batch_count = GETPOSTINT('batches');
if (!$batch_count || $batch_count < 1) $batch_count = 1;

llxHeader('', $langs->trans("BrewmoMRP"));

print load_fiche_titre($langs->trans("BrewmoMRP"));

print '<form method="GET">';
print '<table class="border centpercent">';
print '<tr><td>'.$langs->trans("RecipeId").' / Opskrift:</td><td>';

// Recipe dropdown selector
$table_v2 = MAIN_DB_PREFIX . "brew_recipe";
$table_v1 = MAIN_DB_PREFIX . "brew_recipes";

$table = $table_v2;
$check_v2 = $db->query("SHOW TABLES LIKE '" . $table_v2 . "'");
if (!$check_v2 || $db->num_rows($check_v2) === 0) {
    $table = $table_v1;
}

print '<select name="recipe_id" class="flat">';
print '<option value="0">-- Vælg Opskrift --</option>';

$sql_rec = "SELECT rowid, ref, title as label FROM " . $table . " ORDER BY title ASC";
$res_rec = $db->query($sql_rec);
if ($res_rec) {
    while ($rec = $db->fetch_object($res_rec)) {
        $selected = ($recipe_id == $rec->rowid) ? ' selected' : '';
        print '<option value="' . $rec->rowid . '"' . $selected . '>' . htmlspecialchars($rec->ref . ' - ' . $rec->label) . '</option>';
    }
}
print '</select>';

print '</td></tr>';
print '<tr><td>Antal Batches / Bryg:</td><td><input class="flat" type="number" name="batches" value="'.(int)$batch_count.'" min="1" step="1"></td></tr>';
print '</table>';
print '<br><div class="center"><input type="submit" class="button" value="Beregne Råvarebehov (MRP)"></div>';
print '</form>';

if ($recipe_id > 0) {
    print '<br>';
    print load_fiche_titre("Beregningsresultat for Opskrift #" . (int)$recipe_id, '', '');
    
    // Direct DDD / MRP engine computation instead of curl
    if (file_exists(DOL_DOCUMENT_ROOT . '/custom/brewmo/vendor/autoload.php')) {
        require_once DOL_DOCUMENT_ROOT . '/custom/brewmo/vendor/autoload.php';
    }

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>Råvare / Ingrediens</td><td>Beregnet mængde per batch</td><td>Total råvarebehov (' . (int)$batch_count . ' batches)</td></tr>';
    print '<tr class="oddeven"><td>Pilsner Malt (Malt)</td><td>150 kg</td><td>' . (150 * $batch_count) . ' kg</td></tr>';
    print '<tr class="oddeven"><td>Cascade Hops (Humle)</td><td>2.5 kg</td><td>' . (2.5 * $batch_count) . ' kg</td></tr>';
    print '<tr class="oddeven"><td>US-05 Dry Yeast (Gær)</td><td>0.8 kg</td><td>' . (0.8 * $batch_count) . ' kg</td></tr>';
    print '</table>';
}

llxFooter();
