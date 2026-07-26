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
if (empty($user->rights->brewmo->read)) accessforbidden();

$recipe_id   = GETPOSTINT('recipe_id');
$batch_count = GETPOST('batches', 'alpha');
if (!$batch_count) $batch_count = 1;

llxHeader('', $langs->trans("BrewmoMRP"));

print load_fiche_titre($langs->trans("BrewmoMRP"));

print '<form method="GET">';
print '<table class="border centpercent">';
print '<tr><td>'.$langs->trans("RecipeId").'</td><td><input class="flat" type="number" name="recipe_id" value="'.(int)$recipe_id.'"></td></tr>';
print '<tr><td>'.$langs->trans("Batches").'</td><td><input class="flat" type="number" name="batches" value="'.dol_escape_htmltag($batch_count).'" min="1" step="1"></td></tr>';
print '</table>';
print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Calculate").'"></div>';
print '</form>';

if ($recipe_id > 0) {
    $url = dol_buildpath('/brewmo/api/mrp_calc.php', 1).'?recipe_id='.$recipe_id.'&batches='.$batch_count;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $json = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($json, true);

    if (!empty($data['error'])) {
        print '<div class="error">'.$data['error'].'</div>';
    } else {
        print '<h3>'.$langs->trans("Malts").'</h3>';
        print '<table class="liste"><tr class="liste_titre"><th>Ref</th><th>'.$langs->trans("Label").'</th><th>Behov (kg)</th><th>Lager (kg)</th><th>Bestilles (kg)</th></tr>';
        foreach ($data['malts'] as $row) {
            print '<tr>';
            print '<td>'.dol_escape_htmltag($row['ref']).'</td>';
            print '<td>'.dol_escape_htmltag($row['label']).'</td>';
            print '<td>'.price($row['needed_kg']).'</td>';
            print '<td>'.price($row['stock_kg']).'</td>';
            print '<td>'.price($row['to_order_kg']).'</td>';
            print '</tr>';
        }
        print '</table>';
    }
}

llxFooter();
